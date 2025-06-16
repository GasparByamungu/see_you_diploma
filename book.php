<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if minibus ID is provided
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$minibus_id = (int)$_GET['id'];
$selected_date = $_GET['date'] ?? date('Y-m-d');

// Get minibus details
$stmt = $pdo->prepare("
    SELECT m.*, d.name as driver_name, d.phone as driver_phone,
           GROUP_CONCAT(mi.image_path ORDER BY mi.display_order) as images
    FROM minibuses m 
    LEFT JOIN drivers d ON m.driver_id = d.id
    LEFT JOIN minibus_images mi ON m.id = mi.minibus_id 
    WHERE m.id = ? AND m.status = 'available'
    GROUP BY m.id
");
$stmt->execute([$minibus_id]);
$minibus = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$minibus) {
    header("Location: index.php");
    exit();
}

$images = $minibus['images'] ? explode(',', $minibus['images']) : ['assets/images/default-minibus.jpg'];
$features = json_decode($minibus['features'] ?? '[]', true);

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Form submitted (debug removed for performance)
    try {
        // Validate required fields
        $required_fields = [
            'start_date' => 'Pickup Date',
            'pickup_time' => 'Pickup Time',
            'pickup_location' => 'Pickup Location',
            'pickup_latitude' => 'Pickup Location (Map)',
            'pickup_longitude' => 'Pickup Location (Map)',
            'route_distance' => 'Route Distance'
        ];

        $missing_fields = [];
        foreach ($required_fields as $field => $label) {
            if (empty($_POST[$field])) {
                $missing_fields[] = $label;
            }
        }

        if (!empty($missing_fields)) {
            throw new Exception("Please fill in all required fields: " . implode(", ", $missing_fields));
        }

        // Check if selected date and time are in the past
        $selected_datetime = new DateTime($_POST['start_date'] . ' ' . $_POST['pickup_time']);
        $current_datetime = new DateTime();
        
        if ($selected_datetime <= $current_datetime) {
            throw new Exception("Please select a future date and time. You cannot book for a time that has already passed.");
        }

        // Validate coordinates
        $pickup_latitude = floatval($_POST['pickup_latitude']);
        $pickup_longitude = floatval($_POST['pickup_longitude']);

        if ($pickup_latitude === -6.7924 && $pickup_longitude === 39.2083) {
            throw new Exception("Please select a pickup location on the map by clicking the 'Set Pickup Location' button and then clicking on the map");
        }

        // Validate coordinates are within reasonable range for Tanzania
        if ($pickup_latitude < -12 || $pickup_latitude > -1 || 
            $pickup_longitude < 29 || $pickup_longitude > 41) {
            throw new Exception("Please select a location within Tanzania");
        }

        $start_date = $_POST['start_date'];
        $pickup_time = $_POST['pickup_time'];
        $pickup_location = $_POST['pickup_location'];
        
        // Get dropoff location if provided
        $dropoff_location = !empty($_POST['dropoff_location']) ? $_POST['dropoff_location'] : null;
        $dropoff_latitude = !empty($_POST['dropoff_latitude']) ? floatval($_POST['dropoff_latitude']) : null;
        $dropoff_longitude = !empty($_POST['dropoff_longitude']) ? floatval($_POST['dropoff_longitude']) : null;
        
        // Calculate duration in minutes
        $pickup = new DateTime($start_date . ' ' . $pickup_time);
        $dropoff = new DateTime($start_date . ' ' . $pickup_time);
        $duration = $pickup->diff($dropoff);
        $duration_minutes = ($duration->days * 24 * 60) + ($duration->h * 60) + $duration->i;
        
        // Calculate total price based on distance
        $route_distance = floatval($_POST['route_distance']);
        $price_per_km = floatval($minibus['price_per_km']);
        $total_price = ceil($route_distance * $price_per_km);

        // Validate minimum booking amount
        if ($total_price < 10000) {
            throw new Exception("Minimum booking amount is TZS 10,000");
        }

        // Check if minibus is available for the selected date and time
        $stmt = $pdo->prepare("
            SELECT b.*, u.name as booked_by_name, u.phone as booked_by_phone
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            WHERE b.minibus_id = ? 
            AND b.status != 'cancelled'
            AND b.start_date = ?
            AND b.pickup_time = ?
        ");
        $stmt->execute([
            $minibus_id, 
            $start_date,
            $pickup_time
        ]);
        $existing_booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_booking) {
            // Format the dates for better readability
            $existing_start = date('M d, Y', strtotime($existing_booking['start_date']));
            $existing_time = date('h:i A', strtotime($existing_booking['pickup_time']));
            
            $error_message = "This minibus is already booked for your selected date and time. Here are the details:<br><br>";
            $error_message .= "<strong>Booked by:</strong> " . htmlspecialchars($existing_booking['booked_by_name']) . "<br>";
            $error_message .= "<strong>Date:</strong> " . $existing_start . "<br>";
            $error_message .= "<strong>Pickup Time:</strong> " . $existing_time . "<br><br>";
            $error_message .= "Please try:<br>";
            $error_message .= "1. Selecting a different date or time<br>";
            $error_message .= "2. Choosing a different minibus<br>";
            $error_message .= "3. Contacting us for assistance";
            
            throw new Exception($error_message);
        }

        // Get any additional notes
        $notes = !empty($_POST['notes']) ? $_POST['notes'] : null;

        // Create booking
        $sql = "INSERT INTO bookings (
            minibus_id, user_id, start_date, pickup_time,
            pickup_location, pickup_latitude, pickup_longitude,
            dropoff_location, dropoff_latitude, dropoff_longitude,
            route_distance, route_duration, total_price,
            notes, status
        ) VALUES (
            :minibus_id, :user_id, :start_date, :pickup_time,
            :pickup_location, :pickup_latitude, :pickup_longitude,
            :dropoff_location, :dropoff_latitude, :dropoff_longitude,
            :route_distance, :route_duration, :total_price,
            :notes, 'pending'
        )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'minibus_id' => $minibus_id,
            'user_id' => $_SESSION['user_id'],
            'start_date' => $start_date,
            'pickup_time' => $pickup_time,
            'pickup_location' => $pickup_location,
            'pickup_latitude' => $pickup_latitude,
            'pickup_longitude' => $pickup_longitude,
            'dropoff_location' => $dropoff_location,
            'dropoff_latitude' => $dropoff_latitude,
            'dropoff_longitude' => $dropoff_longitude,
            'route_distance' => $route_distance,
            'route_duration' => $duration_minutes,
            'total_price' => $total_price,
            'notes' => $notes
        ]);

        // Get the booking ID
        $booking_id = $pdo->lastInsertId();

        // Redirect to success page with booking ID
        header("Location: bookings.php?success=1&booking_id=" . $booking_id);
        exit;
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book <?php echo htmlspecialchars($minibus['name']); ?> - Safari Minibus Rentals</title>
    <meta name="description" content="Book your <?php echo htmlspecialchars($minibus['name']); ?> minibus rental with Safari Minibus Rentals. Easy booking process with interactive map selection.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
    <link rel="stylesheet" href="assets/css/style.css?v=1.0">
    <style>
        .booking-summary {
            background: var(--gradient-secondary);
            color: white;
            padding: 2rem;
            border-radius: var(--radius-lg);
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
        }

        .booking-summary h3 {
            color: white;
            margin-bottom: 1.5rem;
            font-weight: 700;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .summary-item.total {
            font-size: 1.25rem;
            font-weight: 700;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid rgba(255, 255, 255, 0.3);
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            padding: 10px;
            background: rgba(45, 80, 22, 0.05);
            border-radius: var(--radius-sm);
            transition: all var(--transition-normal);
        }

        .feature-item:hover {
            background: rgba(45, 80, 22, 0.1);
            transform: translateX(5px);
        }

        .feature-icon {
            font-size: 1.5rem;
            color: var(--primary-color);
            min-width: 30px;
        }

        .price-tag {
            font-size: 2rem;
            font-weight: 800;
            color: var(--accent-color);
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .hero-section {
            background: var(--gradient-primary);
            color: white;
            padding: 2rem 0;
            margin-top: 80px;
        }

        .booking-card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            border: none;
            overflow: hidden;
            position: sticky;
            top: 100px;
        }

        .vehicle-card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: none;
            overflow: hidden;
        }
        .map-container {
            margin: 20px 0;
            position: relative;
            background: #f8f9fa;
            border-radius: 8px;
            overflow: hidden;
            min-height: 400px;
        }
        
        .map-controls {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .map-control-btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            background: #fff;
        }
        
        .map-control-btn i {
            font-size: 16px;
        }
        
        .pickup-btn {
            background-color: #007bff;
        }
        
        .pickup-btn:hover {
            background-color: #0056b3;
        }
        
        .dropoff-btn {
            background-color: #28a745;
        }
        
        .dropoff-btn:hover {
            background-color: #218838;
        }
        
        .map-control-btn.active {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .map-control-btn span {
            white-space: nowrap;
        }
        
        #routeMap {
            height: 400px;
            width: 100%;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            will-change: transform;
            transform: translateZ(0);
            -webkit-backface-visibility: hidden;
            backface-visibility: hidden;
            background: #f8f9fa;
            z-index: 1;
        }
        
        .map-loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 255, 255, 0.95);
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: #333;
        }
        
        .map-loading i {
            color: #007bff;
            font-size: 20px;
        }
        
        .leaflet-container {
            background: #f8f9fa !important;
        }
        
        .leaflet-control-container .leaflet-control {
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .leaflet-control-geocoder {
            width: 300px !important;
        }
        
        .leaflet-control-geocoder-form input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .leaflet-control-geocoder-form input:focus {
            outline: none;
            border-color: #007bff;
        }
        
        .leaflet-control-geocoder-alternatives {
            max-height: 200px;
            overflow-y: auto;
            font-size: 14px;
        }
        
        .leaflet-control-geocoder-alternatives a {
            padding: 8px 12px;
            display: block;
            border-bottom: 1px solid #eee;
        }
        
        .leaflet-control-geocoder-alternatives a:hover {
            background: #f8f9fa;
        }
        
        .leaflet-routing-container {
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 10px;
            font-size: 14px;
        }
        
        .leaflet-routing-container h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #333;
        }
        
        .leaflet-routing-container .leaflet-routing-alternatives-container {
            max-height: 200px;
            overflow-y: auto;
        }
        
        .leaflet-routing-container .leaflet-routing-alternative {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        
        .leaflet-routing-container .leaflet-routing-alternative:hover {
            background: #f8f9fa;
        }
        
        .pickup-marker, .dropoff-marker {
            display: flex;
            align-items: center;
            justify-content: center;
            background: none;
            border: none;
        }
        
        .pickup-marker i {
            color: #007bff;
            font-size: 24px;
            filter: drop-shadow(0 2px 2px rgba(0,0,0,0.3));
        }
        
        .dropoff-marker i {
            color: #28a745;
            font-size: 24px;
            filter: drop-shadow(0 2px 2px rgba(0,0,0,0.3));
        }

        .location-buttons {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: auto;
        }

        .location-btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            pointer-events: auto;
        }

        .location-btn i {
            font-size: 16px;
        }

        .pickup-btn {
            background-color: #007bff;
        }

        .pickup-btn:hover {
            background-color: #0056b3;
        }

        .dropoff-btn {
            background-color: #28a745;
        }

        .dropoff-btn:hover {
            background-color: #218838;
        }

        .location-btn.active {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .location-btn span {
            white-space: nowrap;
        }

        .location-display {
            margin: 20px 0;
            display: flex;
            gap: 20px;
        }

        .location-box {
            flex: 1;
            background: #fff;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }

        .location-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .pickup-box .location-icon {
            background: rgba(0, 123, 255, 0.1);
        }

        .pickup-box .location-icon i {
            color: #007bff;
        }

        .dropoff-box .location-icon {
            background: rgba(40, 167, 69, 0.1);
        }

        .dropoff-box .location-icon i {
            color: #28a745;
        }

        .location-details {
            flex: 1;
        }

        .location-details h4 {
            margin: 0 0 5px 0;
            font-size: 14px;
            color: #666;
        }

        .location-details p {
            margin: 0;
            font-size: 16px;
            color: #333;
            line-height: 1.4;
        }

        .location-box.empty p {
            color: #999;
            font-style: italic;
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            padding: 12px 24px;
            font-size: 18px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-primary i {
            font-size: 20px;
        }

        /* Add validation styles */
        .form-control:invalid {
            border-color: #dc3545;
        }

        .form-control:valid {
            border-color: #198754;
        }

        .invalid-feedback {
            display: none;
            color: #dc3545;
            font-size: 14px;
            margin-top: 5px;
        }

        .form-control:invalid + .invalid-feedback {
            display: block;
        }

        .leaflet-routing-container {
            display: none;
        }
        .leaflet-routing-alternatives-container {
            display: none;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg fixed-top" id="mainNavbar">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="bi bi-truck me-2 fs-4"></i>
                <span>Safari Minibus Rentals</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-house me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="services.php">
                            <i class="bi bi-gear me-1"></i>Our Services
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">
                            <i class="bi bi-info-circle me-1"></i>About Us
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="bookings.php">
                            <i class="bi bi-calendar-check me-1"></i>My Bookings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="bi bi-person me-1"></i>My Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right me-1"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <nav aria-label="breadcrumb" class="mb-3">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="index.php" class="text-white text-decoration-none">
                                    <i class="bi bi-house me-1"></i>Home
                                </a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="view_minibus.php?id=<?php echo $minibus['id']; ?>" class="text-white text-decoration-none">
                                    <?php echo htmlspecialchars($minibus['name']); ?>
                                </a>
                            </li>
                            <li class="breadcrumb-item active text-white" aria-current="page">
                                Book Now
                            </li>
                        </ol>
                    </nav>
                    <h1 class="display-5 fw-bold mb-3">
                        <i class="bi bi-calendar-plus me-3"></i>Book Your Journey
                    </h1>
                    <p class="lead mb-0">
                        Complete your booking for <strong><?php echo htmlspecialchars($minibus['name']); ?></strong>
                        and start your adventure across Tanzania
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="price-display">
                        <div class="price-tag">TZS <?php echo number_format($minibus['price_per_km']); ?></div>
                        <small class="text-white opacity-75">per kilometer</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Booking Content -->
    <div class="container py-5">
        <div class="row">
            <!-- Minibus Details -->
            <div class="col-lg-8 mb-4">
                <div class="vehicle-card mb-4">
                    <div class="card-header bg-white border-0 py-4">
                        <h2 class="mb-0 text-primary fw-bold">
                            <i class="bi bi-truck me-3"></i><?php echo htmlspecialchars($minibus['name']); ?>
                        </h2>
                        <p class="text-muted mb-0">Review vehicle details before booking</p>
                    </div>
                    <div class="card-body p-0">
                        <!-- Image Carousel -->
                        <div id="minibusCarousel" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-inner">
                                <?php foreach($images as $index => $image): ?>
                                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                    <img src="<?php echo htmlspecialchars($image); ?>"
                                         class="d-block w-100"
                                         alt="<?php echo htmlspecialchars($minibus['name']); ?>"
                                         style="height: 400px; object-fit: cover;">
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if(count($images) > 1): ?>
                            <button class="carousel-control-prev" type="button" data-bs-target="#minibusCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#minibusCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                            <?php endif; ?>
                        </div>

                        <!-- Vehicle Information -->
                        <div class="p-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="text-primary mb-3">
                                        <i class="bi bi-info-circle me-2"></i>Vehicle Specifications
                                    </h5>
                                    <div class="feature-item">
                                        <i class="bi bi-people feature-icon"></i>
                                        <span><strong>Capacity:</strong> <?php echo htmlspecialchars($minibus['capacity']); ?> passengers</span>
                                    </div>
                                    <div class="feature-item">
                                        <i class="bi bi-shield-check feature-icon"></i>
                                        <span><strong>Status:</strong> Available & Ready</span>
                                    </div>
                                    <div class="feature-item">
                                        <i class="bi bi-geo-alt feature-icon"></i>
                                        <span><strong>Coverage:</strong> All Tanzania</span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <?php if (isset($minibus['driver_name']) && $minibus['driver_name']): ?>
                                    <h5 class="text-primary mb-3">
                                        <i class="bi bi-person-badge me-2"></i>Your Driver
                                    </h5>
                                    <div class="feature-item">
                                        <i class="bi bi-person feature-icon"></i>
                                        <span><strong>Name:</strong> <?php echo htmlspecialchars($minibus['driver_name']); ?></span>
                                    </div>
                                    <?php if(isset($minibus['driver_phone']) && $minibus['driver_phone']): ?>
                                    <div class="feature-item">
                                        <i class="bi bi-telephone feature-icon"></i>
                                        <span><strong>Contact:</strong>
                                            <a href="tel:<?php echo htmlspecialchars($minibus['driver_phone']); ?>"
                                               class="text-decoration-none text-primary">
                                                <?php echo htmlspecialchars($minibus['driver_phone']); ?>
                                            </a>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                    <?php else: ?>
                                    <h5 class="text-primary mb-3">
                                        <i class="bi bi-person-badge me-2"></i>Driver Assignment
                                    </h5>
                                    <div class="feature-item">
                                        <i class="bi bi-clock feature-icon"></i>
                                        <span>Professional driver will be assigned after booking confirmation</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if(!empty($features)): ?>
                            <hr class="my-4">
                            <h5 class="text-primary mb-3">
                                <i class="bi bi-star me-2"></i>Features & Amenities
                            </h5>
                            <div class="row">
                                <?php foreach ($features as $feature):
                                    $icon = match($feature) {
                                        'Android TV' => 'bi-tv',
                                        'Music Sound System' => 'bi-music-note-beamed',
                                        'Fridge' => 'bi-snow',
                                        'Luggage Space' => 'bi-briefcase-fill',
                                        'Charging System' => 'bi-lightning-charge-fill',
                                        default => 'bi-check-circle-fill'
                                    };
                                ?>
                                <div class="col-md-6">
                                    <div class="feature-item">
                                        <i class="bi <?php echo $icon; ?> feature-icon"></i>
                                        <span><?php echo htmlspecialchars($feature); ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booking Form -->
            <div class="col-lg-4">
                <div class="booking-card">
                    <div class="card-header bg-white border-0 py-4">
                        <h3 class="mb-0 text-primary fw-bold">
                            <i class="bi bi-calendar-plus me-2"></i>Complete Your Booking
                        </h3>
                        <p class="text-muted mb-0">Fill in the details to reserve your minibus</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger d-flex align-items-start" role="alert">
                                <i class="bi bi-exclamation-triangle me-2 mt-1"></i>
                                <div>
                                    <?php
                                        // Check if the error message contains HTML (our custom booking conflict message)
                                        if (strpos($error_message, '<br>') !== false) {
                                            echo $error_message;
                                        } else {
                                            echo htmlspecialchars($error_message);
                                        }
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($success_message): ?>
                            <div class="alert alert-success d-flex align-items-center" role="alert">
                                <i class="bi bi-check-circle me-2"></i>
                                <div><?php echo htmlspecialchars($success_message); ?></div>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $minibus_id); ?>" id="bookingForm">
                                <input type="hidden" id="minibus_id" name="minibus_id" value="<?php echo $minibus['id']; ?>">
                                <input type="hidden" id="minibus_name" name="minibus_name" value="<?php echo htmlspecialchars($minibus['name']); ?>">
                                <input type="hidden" id="capacity" name="capacity" value="<?php echo $minibus['capacity']; ?>">
                                <input type="hidden" id="price_per_km" name="price_per_km" value="<?php echo $minibus['price_per_km']; ?>">
                                <input type="hidden" id="pickup_latitude" name="pickup_latitude" value="-6.7924">
                                <input type="hidden" id="pickup_longitude" name="pickup_longitude" value="39.2083">
                                <input type="hidden" id="pickup_location" name="pickup_location" value="Dar es Salaam, Tanzania">
                                <input type="hidden" id="dropoff_latitude" name="dropoff_latitude" value="-6.7924">
                                <input type="hidden" id="dropoff_longitude" name="dropoff_longitude" value="39.2083">
                                <input type="hidden" id="dropoff_location" name="dropoff_location" value="">
                                <input type="hidden" id="route_distance" name="route_distance" value="0">
                                <input type="hidden" id="route_duration" name="route_duration" value="0">
                                <input type="hidden" id="total_price" name="total_price" value="0">
                                <!-- Pickup Date and Time -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6 class="text-primary mb-3">
                                            <i class="bi bi-calendar me-2"></i>When do you need the minibus?
                                        </h6>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="start_date" class="form-label fw-semibold">
                                            <i class="bi bi-calendar-date me-2 text-primary"></i>Pickup Date
                                        </label>
                                        <input type="date" class="form-control form-control-lg" id="start_date" name="start_date"
                                               value="<?php echo $selected_date; ?>"
                                               min="<?php echo date('Y-m-d'); ?>" required>
                                        <small class="text-muted">Select your preferred pickup date</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="pickup_time" class="form-label fw-semibold">
                                            <i class="bi bi-clock me-2 text-primary"></i>Pickup Time
                                        </label>
                                        <select class="form-select form-select-lg" id="pickup_time" name="pickup_time" required style="display: block !important; visibility: visible !important; opacity: 1 !important;">
                                            <option value="" disabled selected>Select pickup time</option>
                                            <?php
                                            // Generate time options in 12-hour format
                                            for ($hour = 0; $hour < 24; $hour++) {
                                                // Format for o'clock times
                                                $time = sprintf("%02d:00", $hour);
                                                $display = date("g:i A", strtotime($time));
                                                echo "<option value=\"$time\">$display</option>";
                                                
                                                // Format for half past times
                                                $time = sprintf("%02d:30", $hour);
                                                $display = date("g:i A", strtotime($time));
                                                echo "<option value=\"$time\">$display</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Map Section -->
                                <div class="mb-4">
                                    <h6 class="text-primary mb-3">
                                        <i class="bi bi-geo-alt me-2"></i>Select Pickup & Dropoff Locations
                                    </h6>
                                    <p class="text-muted mb-3">Click on the map to set your pickup and dropoff locations. The route distance will be calculated automatically.</p>
                                </div>

                                <div class="map-container">
                                    <div id="routeMap" style="height: 400px; width: 100%; position: relative;"></div>
                                    <div class="map-controls">
                                        <button id="pickupLocationBtn" class="map-control-btn pickup-btn active">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span>Set Pickup Location</span>
                                        </button>
                                        <button id="dropoffLocationBtn" class="map-control-btn dropoff-btn">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span>Set Dropoff Location</span>
                                        </button>
                                    </div>
                                    <div class="map-loading">
                                        <i class="fas fa-spinner fa-spin"></i> Loading map...
                                    </div>
                                </div>

                                <!-- Location Display Boxes -->
                                <div class="location-display">
                                    <div class="location-box pickup-box">
                                        <div class="location-icon">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </div>
                                        <div class="location-details">
                                            <h4>Pickup Location</h4>
                                            <p id="pickupLocationDisplay">Select pickup location on the map</p>
                                        </div>
                                    </div>
                                    <div class="location-box dropoff-box">
                                        <div class="location-icon">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </div>
                                        <div class="location-details">
                                            <h4>Dropoff Location</h4>
                                            <p id="dropoffLocationDisplay">Select dropoff location on the map</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="booking-summary">
                                    <h3>Booking Summary</h3>
                                    <div class="summary-item">
                                        <strong>Pickup Date:</strong>
                                        <span id="summary_pickup_date"></span>
                                    </div>
                                    <div class="summary-item">
                                        <strong>Pickup Time:</strong>
                                        <span id="summary_pickup_time"></span>
                                    </div>
                                    <div class="summary-item">
                                        <strong>Route:</strong>
                                        <span id="summary_pickup_location"></span>
                                    </div>
                                    <div class="summary-item">
                                        <strong>Route Distance:</strong>
                                        <span id="summary_route_distance"></span>
                                    </div>
                                    <div class="summary-item">
                                        <strong>Estimated Duration:</strong>
                                        <span id="summary_route_duration"></span>
                                    </div>
                                    <div class="summary-item total">
                                        <strong>Total Price:</strong>
                                        <span id="summary_total_price"></span>
                                    </div>
                                </div>

                                <!-- Add Book Now button -->
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg w-100" id="bookNowBtn">
                                        <i class="bi bi-calendar-check me-2"></i>Complete Booking
                                    </button>
                                    <small class="text-muted d-block text-center mt-2">
                                        <i class="bi bi-shield-check me-1"></i>
                                        Secure booking with instant confirmation
                                    </small>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5>
                        <i class="bi bi-truck me-2"></i>Safari Minibus Rentals
                    </h5>
                    <p class="mb-3">
                        Your trusted partner for comfortable and reliable minibus transportation
                        across Tanzania. Experience the beauty of our country with our premium fleet.
                    </p>
                </div>

                <div class="col-lg-2 col-md-6 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="index.php"><i class="bi bi-house me-2"></i>Home</a></li>
                        <li class="mb-2"><a href="about.php"><i class="bi bi-info-circle me-2"></i>About Us</a></li>
                        <li class="mb-2"><a href="services.php"><i class="bi bi-gear me-2"></i>Our Services</a></li>
                        <li class="mb-2"><a href="bookings.php"><i class="bi bi-calendar-check me-2"></i>My Bookings</a></li>
                        <li class="mb-2"><a href="profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <h5>Contact Information</h5>
                    <div class="mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-telephone me-3 text-accent"></i>
                            <span>+255 683749514</span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-envelope me-3 text-accent"></i>
                            <span>info@safariminibus.co.tz</span>
                        </div>
                        <div class="d-flex align-items-start">
                            <i class="bi bi-geo-alt me-3 text-accent mt-1"></i>
                            <span>Dar es Salaam, Tanzania</span>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-4" style="border-color: rgba(255, 255, 255, 0.2);">

            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">
                        &copy; <?php echo date('Y'); ?> Safari Minibus Rentals. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button type="button" class="btn btn-primary position-fixed bottom-0 end-0 m-4 rounded-circle" id="backToTop" style="width: 50px; height: 50px; display: none; z-index: 1000;">
        <i class="bi bi-arrow-up"></i>
    </button>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Show loading indicator
        const loadingIndicator = document.querySelector('.map-loading');
        loadingIndicator.style.display = 'flex';

        // Initialize map with optimized options
        const routeMap = L.map('routeMap', {
            center: [-6.7924, 39.2083],
            zoom: 13,
            zoomControl: true,
            attributionControl: true,
            preferCanvas: true, // Use canvas renderer for better performance
            maxZoom: 18,
            minZoom: 5
        });

        // Add tile layer with optimized options
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 18,
            minZoom: 5,
            subdomains: 'abc', // Use multiple subdomains for better performance
            updateWhenIdle: true, // Only update tiles when map is idle
            updateWhenZooming: false // Don't update tiles while zooming
        }).addTo(routeMap);

        // Initialize markers with optimized icons
        const pickupMarker = L.marker([-6.7924, 39.2083], {
            draggable: true,
            icon: L.divIcon({
                className: 'pickup-marker',
                html: '<i class="fas fa-map-marker-alt"></i>',
                iconSize: [30, 30]
            })
        }).addTo(routeMap);

        const dropoffMarker = L.marker([-6.7924, 39.2083], {
            draggable: true,
            icon: L.divIcon({
                className: 'dropoff-marker',
                html: '<i class="fas fa-map-marker-alt"></i>',
                iconSize: [30, 30]
            })
        }).addTo(routeMap);

        // Initialize routing control with optimized options
        const routingControl = L.Routing.control({
            waypoints: [],
            routeWhileDragging: false, // Disable route calculation while dragging for better performance
            show: true,
            addWaypoints: false,
            draggableWaypoints: false,
            lineOptions: {
                styles: [{ color: '#007bff', weight: 4, opacity: 0.8 }]
            },
            createMarker: function() { return null; },
            router: L.Routing.osrmv1({
                serviceUrl: 'https://router.project-osrm.org/route/v1',
                timeout: 15000, // Reduced timeout
                profile: 'driving'
            }),
            useZoomParameter: true,
            showAlternatives: false,
            fitSelectedRoutes: true
        }).addTo(routeMap);

        // Hide loading indicator when map is ready
        routeMap.whenReady(function() {
            loadingIndicator.style.display = 'none';
        });

        // Add debouncing to marker drag events
        let dragTimeout;
        pickupMarker.on('dragend', function(e) {
            clearTimeout(dragTimeout);
            dragTimeout = setTimeout(() => {
                const latlng = e.target.getLatLng();
                document.getElementById('pickup_latitude').value = latlng.lat;
                document.getElementById('pickup_longitude').value = latlng.lng;
                getAddressFromCoordinates(latlng, true).then(() => {
                    updateRoute();
                    updateBookingSummary();
                });
            }, 100);
        });

        dropoffMarker.on('dragend', function(e) {
            clearTimeout(dragTimeout);
            dragTimeout = setTimeout(() => {
                const latlng = e.target.getLatLng();
                document.getElementById('dropoff_latitude').value = latlng.lat;
                document.getElementById('dropoff_longitude').value = latlng.lng;
                getAddressFromCoordinates(latlng, false).then(() => {
                    updateRoute();
                    updateBookingSummary();
                });
            }, 100);
        });

        // Add debouncing to map click events
        let clickTimeout;
        routeMap.on('click', function(e) {
            clearTimeout(clickTimeout);
            clickTimeout = setTimeout(() => {
                const latlng = e.latlng;
                const isPickup = !document.getElementById('pickup_location').value;
                
                if (isPickup) {
                    document.getElementById('pickup_latitude').value = latlng.lat;
                    document.getElementById('pickup_longitude').value = latlng.lng;
                    pickupMarker.setLatLng(latlng);
                    getAddressFromCoordinates(latlng, true).then(() => {
                        updateRoute();
                        updateBookingSummary();
                    });
                } else {
                    document.getElementById('dropoff_latitude').value = latlng.lat;
                    document.getElementById('dropoff_longitude').value = latlng.lng;
                    dropoffMarker.setLatLng(latlng);
                    getAddressFromCoordinates(latlng, false).then(() => {
                        updateRoute();
                        updateBookingSummary();
                    });
                }
            }, 100);
        });

        // Function to update route
        function updateRoute() {
            const pickupLat = parseFloat(document.getElementById('pickup_latitude').value);
            const pickupLng = parseFloat(document.getElementById('pickup_longitude').value);
            const dropoffLat = parseFloat(document.getElementById('dropoff_latitude').value);
            const dropoffLng = parseFloat(document.getElementById('dropoff_longitude').value);
            
            if (!isNaN(pickupLat) && !isNaN(pickupLng) && !isNaN(dropoffLat) && !isNaN(dropoffLng) &&
                (pickupLat !== -6.7924 || pickupLng !== 39.2083) &&
                (dropoffLat !== -6.7924 || dropoffLng !== 39.2083)) {
                
                try {
                    routingControl.setWaypoints([
                        L.latLng(pickupLat, pickupLng),
                        L.latLng(dropoffLat, dropoffLng)
                    ]);

                    // Listen for route calculation completion
                    routingControl.on('routesfound', function(e) {
                        const routes = e.routes;
                        if (routes && routes.length > 0) {
                            const route = routes[0];
                            const distance = (route.summary.totalDistance / 1000).toFixed(1); // Convert to km
                            const duration = Math.round(route.summary.totalTime / 60); // Convert to minutes
                            const pricePerKm = parseFloat(document.getElementById('price_per_km').value);
                            const totalPrice = Math.ceil(distance * pricePerKm);

                            // Update hidden fields
                            document.getElementById('route_distance').value = distance;
                            document.getElementById('route_duration').value = duration;
                            document.getElementById('total_price').value = totalPrice;

                            // Update booking summary
                            updateBookingSummary();
                        }
                    });
                } catch (error) {
                    console.error('Error updating route:', error);
                }
            }
        }

        // Function to update booking summary
        function updateBookingSummary() {
            const pickupDate = document.getElementById('start_date').value;
            const pickupTime = document.getElementById('pickup_time').value;
            const pickupLocation = document.getElementById('pickup_location').value;
            const dropoffLocation = document.getElementById('dropoff_location').value;
            const distance = document.getElementById('route_distance').value;
            const duration = document.getElementById('route_duration').value;
            const totalPrice = document.getElementById('total_price').value;
            
            // Update summary elements
            document.getElementById('summary_pickup_date').textContent = pickupDate ? new Date(pickupDate).toLocaleDateString() : 'Not selected';
            document.getElementById('summary_pickup_time').textContent = pickupTime || 'Not selected';
            document.getElementById('summary_pickup_location').textContent = `From: ${pickupLocation || 'Select pickup location'} To: ${dropoffLocation || 'Select dropoff location'}`;
            document.getElementById('summary_route_distance').textContent = `${distance || '0'} km`;
            document.getElementById('summary_route_duration').textContent = `${duration || '0'} minutes`;
            document.getElementById('summary_total_price').textContent = `TZS ${parseFloat(totalPrice || '0').toLocaleString()}`;
        }

        // Function to get address from coordinates
        async function getAddressFromCoordinates(latlng, isPickup) {
            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latlng.lat}&lon=${latlng.lng}&zoom=18&addressdetails=1`);
                const data = await response.json();
                
                if (data && data.display_name) {
                    const locationName = data.display_name.split(',').slice(0, 3).join(','); // Get first 3 parts of address
                    if (isPickup) {
                        document.getElementById('pickup_location').value = locationName;
                        document.getElementById('pickupLocationDisplay').textContent = locationName;
                    } else {
                        document.getElementById('dropoff_location').value = locationName;
                        document.getElementById('dropoffLocationDisplay').textContent = locationName;
                    }
                    updateBookingSummary();
                }
            } catch (error) {
                console.error('Error getting address:', error);
            }
        }

        // Add event listeners for date and time changes
        document.getElementById('start_date').addEventListener('change', updateBookingSummary);
        document.getElementById('pickup_time').addEventListener('change', updateBookingSummary);

        // Initial update of booking summary
        updateBookingSummary();

        // Function to calculate direct distance (fallback)
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371; // Radius of the earth in km
            const dLat = deg2rad(lat2 - lat1);
            const dLon = deg2rad(lon2 - lon1);
            const a = 
                Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * 
                Math.sin(dLon/2) * Math.sin(dLon/2); 
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)); 
            const distance = R * c; // Distance in km
            return distance;
        }

        function deg2rad(deg) {
            return deg * (Math.PI/180);
        }
    });
    </script>
</body>
</html>