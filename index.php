<?php
session_start();
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Safari Minibus Rentals - Tanzania's Premier Minibus Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
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

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <h1>Welcome to Safari Minibus Rentals</h1>
            <p>Your trusted partner for comfortable and reliable minibus transportation in Tanzania</p>
        </div>
    </div>

    <!-- Search Section -->
    <div class="search-section">
        <div class="container">
            <form action="search.php" method="GET" class="search-form">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="date" class="form-control" name="date" required>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="passengers" required>
                            <option value="">Number of Passengers</option>
                            <option value="21-25">21-25 Passengers</option>
                            <option value="26-30">26-30 Passengers</option>
                            <option value="31-35">31-35 Passengers</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">Search Minibuses</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Available Minibuses Section -->
    <div class="container my-5">
        <h2 class="text-center mb-4">Available Minibuses</h2>
        <div class="row" id="minibus-list">
            <?php
            $stmt = $pdo->query("
                SELECT m.*, GROUP_CONCAT(mi.image_path ORDER BY mi.display_order) as images
                FROM minibuses m 
                LEFT JOIN minibus_images mi ON m.id = mi.minibus_id 
                WHERE m.status = 'available' 
                GROUP BY m.id 
                ORDER BY m.created_at DESC
            ");
            while($minibus = $stmt->fetch(PDO::FETCH_ASSOC)):
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
            <?php endwhile; ?>
        </div>
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
    <script src="assets/js/main.js"></script>
</body>
</html> 