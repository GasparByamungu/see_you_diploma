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
    <title>Edit Minibus - Safari Minibus Rentals Admin</title>
    <meta name="description" content="Edit minibus details, manage images, and assign drivers in the Safari Minibus Rentals admin panel.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        .admin-header {
            background: var(--gradient-primary);
            color: white;
            padding: 2rem 0;
            margin-top: 80px;
        }

        .form-card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            border: none;
            overflow: hidden;
        }

        .form-section {
            padding: 2rem;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }

        .form-section:last-child {
            border-bottom: none;
        }

        .section-title {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items-center;
            gap: 0.75rem;
        }

        .image-preview {
            position: relative;
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: all var(--transition-normal);
        }

        .image-preview:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .image-preview img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .delete-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(220, 53, 69, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity var(--transition-normal);
        }

        .image-preview.marked-for-deletion .delete-overlay {
            opacity: 1;
        }

        .driver-info-card {
            background: var(--light-gray);
            border-radius: var(--radius-md);
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: var(--radius-lg);
            font-weight: 600;
            font-size: 0.875rem;
        }

        .status-available {
            background: var(--success-color);
            color: white;
        }

        .status-maintenance {
            background: var(--warning-color);
            color: white;
        }

        .status-unavailable {
            background: var(--danger-color);
            color: white;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg fixed-top admin-navbar" id="mainNavbar">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="../index.php">
                <i class="bi bi-shield-check me-2 fs-4"></i>
                <span>Safari Admin</span>
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
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">
                            <i class="bi bi-box-arrow-right me-1"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Admin Header -->
    <section class="admin-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <nav aria-label="breadcrumb" class="mb-3">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="dashboard.php" class="text-white text-decoration-none">
                                    <i class="bi bi-speedometer2 me-1"></i>Dashboard
                                </a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="minibuses.php" class="text-white text-decoration-none">
                                    <i class="bi bi-truck me-1"></i>Minibuses
                                </a>
                            </li>
                            <li class="breadcrumb-item active text-white" aria-current="page">
                                Edit Minibus
                            </li>
                        </ol>
                    </nav>
                    <h1 class="display-6 fw-bold mb-3">
                        <i class="bi bi-pencil-square me-3"></i>Edit Minibus
                    </h1>
                    <p class="lead mb-0">
                        Update minibus details, manage images, and assign drivers
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="d-flex flex-column align-items-lg-end">
                        <h4 class="mb-2"><?php echo htmlspecialchars($minibus['name']); ?></h4>
                        <span class="status-badge status-<?php echo $minibus['status']; ?>">
                            <i class="bi bi-<?php echo $minibus['status'] === 'available' ? 'check-circle' : ($minibus['status'] === 'maintenance' ? 'tools' : 'x-circle'); ?> me-1"></i>
                            <?php echo ucfirst($minibus['status']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Edit Minibus Form -->
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-check-circle me-2"></i>
                        <div>
                            <?php
                            echo htmlspecialchars($_SESSION['success_message']);
                            unset($_SESSION['success_message']);
                            ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-start mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle me-2 mt-1"></i>
                        <div class="flex-grow-1">
                            <?php
                            echo htmlspecialchars($_SESSION['error_message']);
                            unset($_SESSION['error_message']);
                            ?>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="form-card">

                    <form action="" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <!-- Basic Information Section -->
                        <div class="form-section">
                            <h4 class="section-title">
                                <i class="bi bi-info-circle"></i>Basic Information
                            </h4>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label fw-semibold">
                                        <i class="bi bi-truck me-2 text-primary"></i>Minibus Name
                                    </label>
                                    <input type="text" class="form-control form-control-lg" id="name" name="name"
                                           value="<?php echo htmlspecialchars($minibus['name']); ?>" required>
                                    <div class="invalid-feedback">
                                        Please provide a valid minibus name.
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="capacity" class="form-label fw-semibold">
                                        <i class="bi bi-people me-2 text-primary"></i>Passenger Capacity
                                    </label>
                                    <input type="number" class="form-control form-control-lg" id="capacity" name="capacity"
                                           value="<?php echo htmlspecialchars($minibus['capacity']); ?>" min="1" max="50" required>
                                    <div class="invalid-feedback">
                                        Please provide a valid capacity (1-50 passengers).
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="price_per_km" class="form-label fw-semibold">
                                        <i class="bi bi-currency-exchange me-2 text-primary"></i>Price per Kilometer (TZS)
                                    </label>
                                    <input type="number" class="form-control form-control-lg" id="price_per_km" name="price_per_km"
                                           value="<?php echo htmlspecialchars($minibus['price_per_km']); ?>" min="100" step="50" required>
                                    <div class="invalid-feedback">
                                        Please provide a valid price per kilometer.
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label fw-semibold">
                                        <i class="bi bi-gear me-2 text-primary"></i>Status
                                    </label>
                                    <select class="form-select form-select-lg" id="status" name="status" required>
                                        <option value="available" <?php echo $minibus['status'] === 'available' ? 'selected' : ''; ?>>
                                            <i class="bi bi-check-circle"></i> Available
                                        </option>
                                        <option value="maintenance" <?php echo $minibus['status'] === 'maintenance' ? 'selected' : ''; ?>>
                                            <i class="bi bi-tools"></i> Under Maintenance
                                        </option>
                                        <option value="unavailable" <?php echo $minibus['status'] === 'unavailable' ? 'selected' : ''; ?>>
                                            <i class="bi bi-x-circle"></i> Unavailable
                                        </option>
                                    </select>
                                    <div class="invalid-feedback">
                                        Please select a status.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Features Section -->
                        <div class="form-section">
                            <h4 class="section-title">
                                <i class="bi bi-star"></i>Features & Amenities
                            </h4>

                            <div class="mb-3">
                                <label for="features" class="form-label fw-semibold">
                                    <i class="bi bi-list-check me-2 text-primary"></i>Available Features
                                </label>
                                <input type="text" class="form-control form-control-lg" id="features" name="features"
                                       value="<?php echo htmlspecialchars(implode(', ', $current_features)); ?>"
                                       placeholder="e.g., Air Conditioning, WiFi, TV, Music System, Charging Ports">
                                <div class="form-text">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Separate multiple features with commas. These will be displayed to customers.
                                </div>
                            </div>
                        </div>

                        <!-- Driver Assignment Section -->
                        <div class="form-section">
                            <h4 class="section-title">
                                <i class="bi bi-person-badge"></i>Driver Assignment
                            </h4>
                            <?php if ($minibus['driver_id']): ?>
                                <div class="driver-info-card mb-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                            <i class="bi bi-person fs-4 text-white"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1 fw-bold">Currently Assigned Driver</h6>
                                            <p class="text-muted mb-0">Active assignment</p>
                                        </div>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-person me-2 text-primary"></i>
                                                <div>
                                                    <small class="text-muted">Name</small>
                                                    <div class="fw-semibold"><?php echo htmlspecialchars($minibus['driver_name']); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-telephone me-2 text-primary"></i>
                                                <div>
                                                    <small class="text-muted">Phone</small>
                                                    <div class="fw-semibold"><?php echo htmlspecialchars($minibus['driver_phone']); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-card-text me-2 text-primary"></i>
                                                <div>
                                                    <small class="text-muted">License</small>
                                                    <div class="fw-semibold"><?php echo htmlspecialchars($minibus['driver_license']); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-check mb-4">
                                    <input class="form-check-input" type="checkbox" id="remove_driver" name="remove_driver">
                                    <label class="form-check-label fw-semibold" for="remove_driver">
                                        <i class="bi bi-person-x me-2 text-danger"></i>
                                        Remove current driver assignment
                                    </label>
                                    <div class="form-text">
                                        Check this box to unassign the current driver from this minibus.
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="edit_driver_id" class="form-label fw-semibold">
                                    <i class="bi bi-person-plus me-2 text-primary"></i>Assign Driver
                                </label>
                                <select class="form-select form-select-lg" id="edit_driver_id" name="driver_id" <?php echo ($minibus['driver_id'] && !isset($_POST['remove_driver'])) ? 'disabled' : ''; ?>>
                                    <option value="">Select a driver (optional)</option>
                                    <?php foreach ($drivers as $driver): ?>
                                        <option value="<?php echo $driver['id']; ?>" <?php echo ($minibus['driver_id'] == $driver['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($driver['name'] . ' - ' . $driver['phone'] . ' (License: ' . $driver['license_number'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Only available drivers are shown. Assigned drivers can be reassigned.
                                </div>
                            </div>
                        </div>

                        <!-- Image Management Section -->
                        <div class="form-section">
                            <h4 class="section-title">
                                <i class="bi bi-images"></i>Image Gallery
                            </h4>

                            <?php if (!empty($images)): ?>
                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-collection me-2 text-primary"></i>Current Images
                                </label>
                                <div class="row g-3">
                                    <?php foreach ($images as $image): ?>
                                    <div class="col-lg-4 col-md-6">
                                        <div class="image-preview" id="image_<?php echo $image['id']; ?>">
                                            <img src="../<?php echo htmlspecialchars($image['image_path']); ?>" alt="Minibus Image">
                                            <div class="delete-overlay">
                                                <i class="bi bi-trash fs-1 text-white"></i>
                                            </div>
                                            <div class="position-absolute top-0 end-0 m-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="delete_images[]"
                                                           value="<?php echo $image['id']; ?>" id="delete_<?php echo $image['id']; ?>"
                                                           onchange="toggleDeleteOverlay(<?php echo $image['id']; ?>)">
                                                    <label class="form-check-label visually-hidden" for="delete_<?php echo $image['id']; ?>">
                                                        Delete Image
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="form-text mt-2">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Check the boxes on images you want to delete. Changes will be applied when you save.
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if (count($images) < 3): ?>
                            <div class="mb-3">
                                <label for="images" class="form-label fw-semibold">
                                    <i class="bi bi-cloud-upload me-2 text-primary"></i>Add New Images
                                </label>
                                <input type="file" class="form-control form-control-lg" id="images" name="images[]"
                                       multiple accept="image/*">
                                <div class="form-text">
                                    <i class="bi bi-info-circle me-1"></i>
                                    You can select multiple images. Maximum file size: 5MB per image.
                                    Allowed formats: JPG, JPEG, PNG. You can add <?php echo 3 - count($images); ?> more image(s).
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-warning d-flex align-items-center">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <div>
                                    Maximum number of images (3) reached. Delete some images above to add new ones.
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Form Actions -->
                        <div class="form-section">
                            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-end">
                                <a href="minibuses.php" class="btn btn-outline-secondary btn-lg">
                                    <i class="bi bi-arrow-left me-2"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary btn-lg" id="updateBtn">
                                    <i class="bi bi-check-circle me-2"></i>Update Minibus
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="admin-footer">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">
                        &copy; <?php echo date('Y'); ?> Safari Minibus Rentals Admin Panel. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">
                        <a href="dashboard.php" class="me-3">Dashboard</a>
                        <a href="../index.php" class="me-3">View Site</a>
                        <a href="../logout.php">Logout</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button type="button" class="btn btn-primary position-fixed bottom-0 end-0 m-4 rounded-circle" id="backToTop" style="width: 50px; height: 50px; display: none; z-index: 1000;">
        <i class="bi bi-arrow-up"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('mainNavbar');
            const backToTop = document.getElementById('backToTop');

            if (window.scrollY > 100) {
                navbar.classList.add('scrolled');
                backToTop.style.display = 'block';
            } else {
                navbar.classList.remove('scrolled');
                backToTop.style.display = 'none';
            }
        });

        // Back to top functionality
        document.getElementById('backToTop').addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Image delete overlay toggle
        function toggleDeleteOverlay(imageId) {
            const checkbox = document.getElementById('delete_' + imageId);
            const imagePreview = document.getElementById('image_' + imageId);

            if (checkbox.checked) {
                imagePreview.classList.add('marked-for-deletion');
            } else {
                imagePreview.classList.remove('marked-for-deletion');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Driver assignment functionality
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

            // Form validation
            const form = document.querySelector('.needs-validation');
            if (form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    } else {
                        // Add loading state to submit button
                        const submitBtn = document.getElementById('updateBtn');
                        const originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Updating...';
                        submitBtn.disabled = true;

                        // Re-enable button after 10 seconds in case of error
                        setTimeout(() => {
                            submitBtn.innerHTML = originalText;
                            submitBtn.disabled = false;
                        }, 10000);
                    }
                    form.classList.add('was-validated');
                }, false);
            }

            // File input validation
            const fileInput = document.getElementById('images');
            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    const files = this.files;
                    const maxSize = 5 * 1024 * 1024; // 5MB
                    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];

                    for (let i = 0; i < files.length; i++) {
                        const file = files[i];

                        if (file.size > maxSize) {
                            alert(`File "${file.name}" is too large. Maximum size is 5MB.`);
                            this.value = '';
                            return;
                        }

                        if (!allowedTypes.includes(file.type)) {
                            alert(`File "${file.name}" is not a valid image type. Only JPG, JPEG, and PNG are allowed.`);
                            this.value = '';
                            return;
                        }
                    }
                });
            }

            // Add animation to form sections
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            // Observe form sections for animation
            document.querySelectorAll('.form-section').forEach((section, index) => {
                section.style.opacity = '0';
                section.style.transform = 'translateY(30px)';
                section.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
                observer.observe(section);
            });
        });
    </script>
</body>
</html> 