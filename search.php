<?php
session_start();
require_once 'config/database.php';

// Get search parameters
$date = $_GET['date'] ?? '';
$passengers = $_GET['passengers'] ?? '';

// Validate date
if (empty($date)) {
    header("Location: index.php");
    exit();
}

// Parse passenger range
$passenger_range = explode('-', $passengers);
$min_passengers = (int)$passenger_range[0];
$max_passengers = (int)$passenger_range[1];

// Get available minibuses for the selected date and capacity
$stmt = $pdo->prepare("
    SELECT m.*, GROUP_CONCAT(mi.image_path ORDER BY mi.display_order) as images
    FROM minibuses m 
    LEFT JOIN minibus_images mi ON m.id = mi.minibus_id 
    WHERE m.capacity BETWEEN ? AND ?
    AND m.id NOT IN (
        SELECT minibus_id 
        FROM bookings 
        WHERE start_date = ? 
        AND status != 'cancelled'
    )
    GROUP BY m.id
");
$stmt->execute([$min_passengers, $max_passengers, $date]);
$minibuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - Safari Minibus Rentals</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .search-summary {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .minibus-card {
            transition: transform 0.2s;
        }
        .minibus-card:hover {
            transform: translateY(-5px);
        }
        .no-results {
            text-align: center;
            padding: 3rem;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Safari Minibus Rentals</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="services.php">Our Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About Us</a>
                    </li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="bookings.php">My Bookings</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">My Profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Search Results -->
    <div class="container my-5">
        <!-- Search Summary -->
        <div class="search-summary">
            <h2>Search Results</h2>
            <p class="mb-0">
                Date: <?php echo date('F j, Y', strtotime($date)); ?><br>
                Passenger Capacity: <?php echo $passengers; ?> passengers
            </p>
        </div>

        <?php if (empty($minibuses)): ?>
            <div class="no-results">
                <i class="bi bi-search" style="font-size: 3rem;"></i>
                <h3 class="mt-3">No Minibuses Found</h3>
                <p class="text-muted">We couldn't find any available minibuses matching your criteria.</p>
                <a href="index.php" class="btn btn-primary">Try Different Search</a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($minibuses as $minibus): 
                    $images = $minibus['images'] ? explode(',', $minibus['images']) : ['assets/images/default-minibus.jpg'];
                ?>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div id="minibusCarousel<?php echo $minibus['id']; ?>" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-inner">
                                <?php foreach($images as $index => $image): ?>
                                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                    <img src="<?php echo htmlspecialchars($image); ?>" class="card-img-top" alt="Minibus" style="height: 200px; object-fit: cover;">
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button class="carousel-control-prev" type="button" data-bs-target="#minibusCarousel<?php echo $minibus['id']; ?>" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#minibusCarousel<?php echo $minibus['id']; ?>" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($minibus['name']); ?></h5>
                            <p class="card-text">
                                Capacity: <?php echo htmlspecialchars($minibus['capacity']); ?> passengers<br>
                                Price: <?php echo number_format($minibus['price_per_km']); ?> TZS per kilometer
                            </p>
                            <div class="d-flex gap-2">
                                <a href="view_minibus.php?id=<?php echo $minibus['id']; ?>" class="btn btn-outline-primary">View Details</a>
                                <?php if(isset($_SESSION['user_id'])): ?>
                                    <a href="book.php?id=<?php echo $minibus['id']; ?>" class="btn btn-primary">Book Now</a>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-primary">Login to Book</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Contact Us</h5>
                    <p>Phone: +255 683749514<br>
                    Email: info@safariminibus.co.tz</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="about.php" class="text-light">About Us</a></li>
                        <li><a href="services.php" class="text-light">Our Services</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 