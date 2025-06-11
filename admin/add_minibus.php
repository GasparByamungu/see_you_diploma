<?php
session_start();
require_once '../config/database.php';

// Debug session information
error_log("Session data: " . print_r($_SESSION, true));

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    error_log("User not logged in");
    header("Location: ../login.php");
    exit();
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    error_log("User role check failed. Role: " . ($_SESSION['user_role'] ?? 'not set'));
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Validate required fields
        if (empty($_POST['name']) || empty($_POST['capacity']) || empty($_POST['price_per_km'])) {
            throw new Exception("Please fill in all required fields");
        }

        // Get all available drivers for dropdown
        $stmt = $pdo->query("SELECT id, name, phone FROM drivers WHERE status = 'available' ORDER BY name");
        $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

        // Insert new minibus
        $stmt = $pdo->prepare("
            INSERT INTO minibuses (
                name, capacity, price_per_km, features, driver_id, status
            ) VALUES (
                :name, :capacity, :price_per_km, :features, :driver_id, :status
            )
        ");
        
        $driver_id = !empty($_POST['driver_id']) ? $_POST['driver_id'] : null;
        
        $stmt->execute([
            'name' => $_POST['name'],
            'capacity' => $_POST['capacity'],
            'price_per_km' => $_POST['price_per_km'],
            'features' => $features_json,
            'driver_id' => $driver_id ?: null,
            'status' => 'available'
        ]);

        $minibus_id = $pdo->lastInsertId();
        error_log("New minibus created with ID: " . $minibus_id . " and features: " . $features_json);

        // Update driver status if assigned
        if ($driver_id) {
            $stmt = $pdo->prepare("UPDATE drivers SET status = 'assigned' WHERE id = ?");
            $stmt->execute([$driver_id]);
            error_log("Driver status updated to 'assigned' for ID: " . $driver_id);
        }

        // Handle image uploads
        if (!empty($_FILES['images']['name'][0])) {
            $upload_dir = '../uploads/minibuses/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_name = $_FILES['images']['name'][$key];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    
                    // Validate file type
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                    if (!in_array($file_ext, $allowed_types)) {
                        throw new Exception("Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.");
                    }
                    
                    $new_file_name = uniqid('minibus_') . '.' . $file_ext;
                    $file_path = $upload_dir . $new_file_name;

                    if (move_uploaded_file($tmp_name, $file_path)) {
                        $stmt = $pdo->prepare("
                            INSERT INTO minibus_images (minibus_id, image_path, display_order)
                            VALUES (?, ?, ?)
                        ");
                        $stmt->execute([$minibus_id, 'uploads/minibuses/' . $new_file_name, $key]);
                        error_log("Image uploaded for minibus ID: " . $minibus_id);
                    } else {
                        throw new Exception("Failed to upload image: " . $file_name);
                    }
                }
            }
        }

        $pdo->commit();
        error_log("Transaction committed successfully for minibus ID: " . $minibus_id);
        $_SESSION['success_message'] = "Minibus added successfully!";
        header("Location: minibuses.php");
        exit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: minibuses.php");
        exit();
    }
} else {
    header("Location: minibuses.php");
    exit();
} 